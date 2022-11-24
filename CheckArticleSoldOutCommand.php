<?php
namespace App\Command;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use LogicException;
class CheckArticleSoldOutCommand extends Command
{
    /**
     * @var EntityManager
     */
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }
  
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $articles = $this->em->getRepository(Article::class)->findAll();
      
      $nb_articles = count($article);
      
      $output->writeln("found $nb_articles articles");
      
      foreach($articles as $article) {

        if ($article->getSoldout() === false) {
        	
          $stock = $this->em->createQueryBuilder('article')
            ->select('article, (COUNT(ownership) - COUNT(trees)) AS stock_remaining')
            ->leftJoin(
                'article.trees',
                'trees',
                'WITH',
                'article = trees.article')
            ->leftJoin(
                'trees.ownership',
                'ownership',
                'WITH',
                'arbres = ownership.tree')
            ->where('article.id = :id')
            ->andWhere('(article.b2c = FALSE AND article.soldout = FALSE) or (article.b2c = TRUE AND article.soldout = FALSE)')
            ->groupBy('article.id')
            ->setParameter('id', $article->getId())
            ->getQuery()->getOneOrNullResult();

          if ($stock <= 0) {
            $article->setSoldout(true);
          }
          $article->setSoldout(false);
        }
      }
      $this->em->flush();
      $output->writeln("Done");
    }
}